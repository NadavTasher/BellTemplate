<?php

include_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "accounts" . DIRECTORY_SEPARATOR . "api.php";

const BELL_API = "bell";

const DATABASE = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "bell" . DIRECTORY_SEPARATOR . "database.json";
const MEDIA_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "media";

$database = json_decode(file_get_contents(DATABASE));

function bell()
{
    $user = accounts();
    if ($user !== null) {
        if (isset($_POST[BELL_API])) {
            $information = json_decode(filter($_POST[BELL_API]));
            if (isset($information->action) && isset($information->parameters)) {
                $action = $information->action;
                $parameters = $information->parameters;
                result(BELL_API, $action, "success", false);
                switch ($action) {
                    case "media-add":
                        if (isset($parameters->name)) {
                            if (bell_ends_with($_FILES["audio"]["tmp_name"], ".mp3")) {
                                $file = random(30) . ".mp3";
                                move_uploaded_file($_FILES["audio"]["tmp_name"], MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
                                bell_media_add($parameters->name, $file);
                                result(BELL_API, $action, "success", true);
                            } else {
                                error(BELL_API, $action, "Wrong media format");
                            }
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "time-add":
                        if (isset($parameters->time)) {
                            bell_time_add($parameters->time);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "time-remove":
                        if (isset($parameters->time)) {
                            bell_time_remove($parameters->time);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "preset-add":
                        if (isset($parameters->preset)) {
                            bell_preset_add($parameters->preset);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "preset-remove":
                        if (isset($parameters->preset)) {
                            bell_preset_remove($parameters->preset);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "preset-set":
                        if (isset($parameters->preset)) {
                            bell_preset_set($parameters->preset);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "queue-add":
                        if (isset($parameters->time) && isset($parameters->preset) && isset($parameters->media) && isset($parameters->second)) {
                            bell_queue_add($parameters->time, $parameters->preset, $parameters->media, $parameters->second);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "queue-remove":
                        if (isset($parameters->time) && isset($parameters->preset)) {
                            bell_queue_remove($parameters->time, $parameters->preset);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "mute-set":
                        if (isset($parameters->mute)) {
                            bell_mute_set($parameters->mute);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                    case "duration-set":
                        if (isset($parameters->duration)) {
                            bell_duration_set($parameters->duration);
                            result(BELL_API, $action, "success", true);
                        } else {
                            error(BELL_API, $action, "Missing information");
                        }
                        break;
                }
                bell_save();
            }
        }
    }
    return null;
}

// General management

function bell_mute_set($mute)
{
    global $database;
    $database->mute = $mute;
}

function bell_duration_set($duration)
{
    global $database;
    $database->duration = $duration;
}

// Combined management

function bell_queue_add($time, $preset, $media, $second)
{
    global $database;
    if (bell_time_exists($time)) {
        if (bell_preset_exists($preset)) {
            if (bell_media_exists($media)) {
                $artifact = new stdClass();
                $artifact->media = $media;
                $artifact->second = is_numeric($second) ? $second : doubleval($second);
                $database->queue->$time->$preset = $artifact;
            }
        }
    }
}

function bell_queue_remove($time, $preset)
{
    global $database;
    if (bell_time_exists($time)) {
        if (bell_preset_exists($preset)) {
            if (isset($database->queue->$time->$preset)) {
                unset($database->queue->$time->$preset);
            }
        }
    }
}

// Preset management

function bell_preset_add($name)
{
    global $database;
    if (!bell_preset_exists($name)) {
        array_push($database->presets, $name);
    }
}

function bell_preset_remove($name)
{
    global $database;
    if (bell_preset_exists($name)) {
        $presets = array();
        foreach ($database->presets as $preset) {
            if ($preset !== $name) {
                array_push($presets, $preset);
            }
        }
        foreach ($database->queue as $time) {
            if (isset($time->$name)) unset($time->$name);
        }
        $database->presets = $presets;
    }
}

function bell_preset_set($name)
{
    global $database;
    if (bell_preset_exists($name)) {
        $database->preset = $name;
    }
}

function bell_preset_exists($name)
{
    global $database;
    foreach ($database->presets as $preset) {
        if ($name === $preset) return true;
    }
    return false;
}

// Media management

function bell_media_add($name, $file)
{
    global $database;
    $mediaID = random(20);
    $media = new stdClass();
    $media->name = $name;
    $media->media = $file;
    $media->hash = sha1_file(MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
    $database->library->$mediaID = $media;
}

function bell_media_exists($id)
{
    global $database;
    return isset($database->library->$id);
}

// Time management

function bell_time_add($time)
{
    global $database;
    if (!bell_time_exists($time)) {
        $database->queue->$time = new stdClass();
    }
}

function bell_time_remove($time)
{
    global $database;
    if (bell_time_exists($time)) {
        unset($database->queue->$time);
    }
}

function bell_time_exists($time)
{
    global $database;
    return isset($database->queue->$time);
}


function bell_ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function bell_save()
{
    global $database;
    file_put_contents(DATABASE, json_encode($database));
}