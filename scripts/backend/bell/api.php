<?php

include_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "accounts" . DIRECTORY_SEPARATOR . "api.php";

const BELL_API = "bell";

const BELL_DATABASE = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "bell" . DIRECTORY_SEPARATOR . "database.json";
const BELL_MEDIA_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "media";

$bell_database = json_decode(file_get_contents(BELL_DATABASE));

function bell()
{
    $user = accounts();
    if ($user !== null) {
        api(BELL_API, function ($action, $parameters) {
            switch ($action) {
                case "media-add":
                    if (isset($parameters->name)) {
                        if (bell_ends_with($_FILES["audio"]["tmp_name"], ".mp3")) {
                            $file = random(30) . ".mp3";
                            move_uploaded_file($_FILES["audio"]["tmp_name"], BELL_MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
                            bell_media_add($parameters->name, $file);
                            return [true, null];
                        } else {
                            return [false, "Wrong media format"];
                        }
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "time-add":
                    if (isset($parameters->time)) {
                        bell_time_add($parameters->time);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "time-remove":
                    if (isset($parameters->time)) {
                        bell_time_remove($parameters->time);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "preset-add":
                    if (isset($parameters->preset)) {
                        bell_preset_add($parameters->preset);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "preset-remove":
                    if (isset($parameters->preset)) {
                        bell_preset_remove($parameters->preset);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "preset-set":
                    if (isset($parameters->preset)) {
                        bell_preset_set($parameters->preset);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "queue-add":
                    if (isset($parameters->time) && isset($parameters->preset) && isset($parameters->media) && isset($parameters->second)) {
                        bell_queue_add($parameters->time, $parameters->preset, $parameters->media, $parameters->second);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "queue-remove":
                    if (isset($parameters->time) && isset($parameters->preset)) {
                        bell_queue_remove($parameters->time, $parameters->preset);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "mute-set":
                    if (isset($parameters->mute)) {
                        bell_mute_set($parameters->mute);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
                case "duration-set":
                    if (isset($parameters->duration)) {
                        bell_duration_set($parameters->duration);
                        return [true, null];
                    } else {
                        return [false, "Missing information"];
                    }
                    break;
            }
            return [false, null];
        }, true);
    }
}

// General management

function bell_mute_set($mute)
{
    global $bell_database;
    $bell_database->mute = $mute;
    bell_save();
}

function bell_duration_set($duration)
{
    global $bell_database;
    $bell_database->duration = $duration;
    bell_save();
}

// Combined management

function bell_queue_add($time, $preset, $media, $second)
{
    global $bell_database;
    if (bell_time_exists($time)) {
        if (bell_preset_exists($preset)) {
            if (bell_media_exists($media)) {
                $artifact = new stdClass();
                $artifact->media = $media;
                $artifact->second = is_numeric($second) ? $second : doubleval($second);
                $bell_database->queue->$time->$preset = $artifact;
                bell_save();
            }
        }
    }
}

function bell_queue_remove($time, $preset)
{
    global $bell_database;
    if (bell_time_exists($time)) {
        if (bell_preset_exists($preset)) {
            if (isset($bell_database->queue->$time->$preset)) {
                unset($bell_database->queue->$time->$preset);
                bell_save();
            }
        }
    }
}

// Preset management

function bell_preset_add($name)
{
    global $bell_database;
    if (!bell_preset_exists($name)) {
        array_push($bell_database->presets, $name);
        bell_save();
    }
}

function bell_preset_remove($name)
{
    global $bell_database;
    if (bell_preset_exists($name)) {
        $presets = array();
        foreach ($bell_database->presets as $preset) {
            if ($preset !== $name) {
                array_push($presets, $preset);
            }
        }
        foreach ($bell_database->queue as $time) {
            if (isset($time->$name)) unset($time->$name);
        }
        $bell_database->presets = $presets;
        bell_save();
    }
}

function bell_preset_set($name)
{
    global $bell_database;
    if (bell_preset_exists($name)) {
        $bell_database->preset = $name;
        bell_save();
    }
}

function bell_preset_exists($name)
{
    global $bell_database;
    foreach ($bell_database->presets as $preset) {
        if ($name === $preset) return true;
    }
    return false;
}

// Media management

function bell_media_add($name, $file)
{
    global $bell_database;
    $mediaID = random(20);
    $media = new stdClass();
    $media->name = $name;
    $media->media = $file;
    $media->hash = sha1_file(BELL_MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
    $bell_database->library->$mediaID = $media;
    bell_save();
}

function bell_media_exists($id)
{
    global $bell_database;
    return isset($bell_database->library->$id);
}

// Time management

function bell_time_add($time)
{
    global $bell_database;
    if (!bell_time_exists($time)) {
        $bell_database->queue->$time = new stdClass();
        bell_save();
    }
}

function bell_time_remove($time)
{
    global $bell_database;
    if (bell_time_exists($time)) {
        unset($bell_database->queue->$time);
        bell_save();
    }
}

function bell_time_exists($time)
{
    global $bell_database;
    return isset($bell_database->queue->$time);
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
    global $bell_database;
    file_put_contents(BELL_DATABASE, json_encode($bell_database));
}