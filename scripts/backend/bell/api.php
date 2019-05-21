<?php

include "../accounts/api.php";

const DATABASE = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "bell" . DIRECTORY_SEPARATOR . "database.json";
const MEDIA_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "media";

$database = json_decode(file_get_contents(DATABASE));

function bell()
{
    $user = accounts();
    if ($user !== null) {
        if (isset($_POST["bell"])) {
            $information = json_decode(bell_filter($_POST["bell"]));
            if (isset($information->action) && isset($information->parameters)) {
                $action = $information->action;
                $parameters = $information->parameters;
                bell_result($action, "success", false);
                switch ($action) {
                    case "upload":
                        if (isset($parameters->name)) {
                            $file = bell_random(30) . ".mp3";
                            move_uploaded_file($_FILES["audio"]["tmp_name"], MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
                            bell_add_media($parameters->name, $file);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "addTime":
                        if (isset($parameters->time)) {
                            bell_add_time($parameters->time);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "removeTime":
                        if (isset($parameters->time)) {
                            bell_remove_time($parameters->time);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "addPreset":
                        if (isset($parameters->preset)) {
                            bell_add_preset($parameters->preset);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "removePreset":
                        if (isset($parameters->preset)) {
                            bell_remove_preset($parameters->preset);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "setPreset":
                        if (isset($parameters->preset)) {
                            bell_set_preset($parameters->preset);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "set":
                        if (isset($parameters->time) && isset($parameters->preset) && isset($parameters->media) && isset($parameters->second)) {
                            bell_set($parameters->time, $parameters->preset, $parameters->media, $parameters->second);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "remove":
                        if (isset($parameters->time) && isset($parameters->preset)) {
                            bell_remove($parameters->time, $parameters->preset);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "mute":
                        if (isset($parameters->mute)) {
                            bell_set_mute($parameters->mute);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
                        }
                        break;
                    case "duration":
                        if (isset($parameters->duration)) {
                            bell_set_duration($parameters->duration);
                            bell_result($action, "success", true);
                        } else {
                            bell_error($action, "Missing information");
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

function bell_set_mute($mute)
{
    global $database;
    $database->mute = $mute;
}

function bell_set_duration($duration)
{
    global $database;
    $database->duration = $duration;
}

// Combined management

function bell_set($time, $preset, $media, $second)
{
    global $database;
    if (bell_has_time($time)) {
        if (bell_has_preset($preset)) {
            if (bell_has_media($media)) {
                $artifact = new stdClass();
                $artifact->media = $media;
                $artifact->time = is_numeric($second) ? $second : doubleval($second);
                $database->queue->$time->$preset = $artifact;
            }
        }
    }
}

function bell_remove($time, $preset)
{
    global $database;
    if (bell_has_time($time)) {
        if (bell_has_preset($preset)) {
            if (isset($database->queue->$time->$preset)) {
                unset($database->queue->$time->$preset);
            }
        }
    }
}

// Preset management

function bell_add_preset($name)
{
    global $database;
    if (!bell_has_preset($name)) {
        array_push($database->presets, $name);
    }
}

function bell_remove_preset($name)
{
    global $database;
    if (bell_has_preset($name)) {
        $presets = array();
        foreach ($database->presets as $preset) {
            if ($preset !== $name) {
                array_push($presets, $preset);
            }
        }
        foreach ($database->queue as $time) {
            if (isset($database->queue->$time->$name)) unset($database->queue->$time->$name);
        }
        $database->presets = $presets;
    }
}

function bell_set_preset($name)
{
    global $database;
    if (bell_has_preset($name)) {
        $database->preset = $name;
    }
}

function bell_has_preset($name)
{
    global $database;
    foreach ($database->presets as $preset) {
        if ($name === $preset) return true;
    }
    return false;
}

// Media management

function bell_add_media($name, $file)
{
    global $database;
    $mediaID = bell_random(20);
    $media = new stdClass();
    $media->name = $name;
    $media->media = $file;
    $media->hash = sha1_file(MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
    $database->library->$mediaID = $media;
}

function bell_has_media($id)
{
    global $database;
    foreach ($database->library as $existent) {
        if ($existent === $id) return true;
    }
    return false;
}

// Time management

function bell_add_time($time)
{
    global $database;
    if (!bell_has_time($time)) {
        $database->queue->$time = new stdClass();
    }
}

function bell_remove_time($time)
{
    global $database;
    if (bell_has_time($time)) {
        unset($database->queue->$time);
    }
}

function bell_has_time($time)
{
    global $database;
    foreach ($database->queue as $existent) {
        if ($existent === $time) return true;
    }
    return false;
}


function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function bell_error($type, $message)
{
    bell_result("errors", $type, $message);
}

function bell_filter($source)
{
    // Filter inputs from XSS and other attacks
    $source = str_replace("<", "", $source);
    $source = str_replace(">", "", $source);
    return $source;
}

function bell_random($length)
{
    $current = str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")[0];
    if ($length > 0) {
        return $current . bell_random($length - 1);
    }
    return "";
}

function bell_result($type, $key, $value)
{
    global $result;
    if (!isset($result->bell)) $result->bell = new stdClass();
    if (!isset($result->bell->$type)) $result->bell->$type = new stdClass();
    $result->bell->$type->$key = $value;
}

function bell_save()
{
    global $database;
    file_put_contents(DATABASE, json_encode($database));
}