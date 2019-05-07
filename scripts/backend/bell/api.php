<?php

const DATABASE = __DIR__ . "/../../../files/bell/database.json";
const MEDIA_DIRECTORY = __DIR__ . "../../../files/media";

$database = json_decode(file_get_contents(DATABASE));

function bell()
{
    $user = accounts();
    if ($user !== null) {
        if (isset($_POST["bell"])) {
            $information = json_decode(filter($_POST["bell"]));
            if (isset($information->action) && isset($information->parameters)) {
                $action = $information->action;
                $parameters = $information->parameters;
                result($action, "success", false);
                switch ($action) {
                    case "upload":
                        if (isset($parameters->name)) {
                            $file = random(30) . ".mp3";
                            move_uploaded_file($_FILES["audio"]["tmp_name"], MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
                            addMedia($parameters->name, $file);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "addTime":
                        if (isset($parameters->time)) {
                            addTime($parameters->time);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "removeTime":
                        if (isset($parameters->time)) {
                            removeTime($parameters->time);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "addPreset":
                        if (isset($parameters->preset)) {
                            addPreset($parameters->preset);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "removePreset":
                        if (isset($parameters->preset)) {
                            removePreset($parameters->preset);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "setPreset":
                        if (isset($parameters->preset)) {
                            setPreset($parameters->preset);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "set":
                        if (isset($parameters->time) && isset($parameters->preset) && isset($parameters->media) && isset($parameters->second)) {
                            set($parameters->time, $parameters->preset, $parameters->media, $parameters->second);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                    case "remove":
                        if (isset($parameters->time) && isset($parameters->preset)) {
                            remove($parameters->time, $parameters->preset);
                            result($action, "success", true);
                        } else {
                            error($action, "Missing information");
                        }
                        break;
                }

            }
        }
    }
    return null;
}

// Combined management

function set($time, $preset, $media, $second)
{
    global $database;
    if (hasTime($time)) {
        if (hasPreset($preset)) {
            if (hasMedia($media)) {
                $artifact = new stdClass();
                $artifact->media = $media;
                $artifact->time = $second;
                $database->queue->$time->$preset = $artifact;
                save();
            }
        }
    }
}

function remove($time, $preset)
{
    global $database;
    if (hasTime($time)) {
        if (hasPreset($preset)) {
            if (isset($database->queue->$time->$preset)) {
                unset($database->queue->$time->$preset);
                save();
            }
        }
    }
}

// Preset management

function addPreset($name)
{
    global $database;
    if (!hasPreset($name)) {
        array_push($database->presets, $name);
        save();
    }
}

function removePreset($name)
{
    global $database;
    if (hasPreset($name)) {
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
        save();
    }
}

function setPreset($name)
{
    global $database;
    if (hasPreset($name)) {
        $database->preset = $name;
        save();
    }
}

function hasPreset($name)
{
    global $database;
    foreach ($database->presets as $preset) {
        if ($name === $preset) return true;
    }
    return false;
}

// Media management

function addMedia($name, $file)
{
    global $database;
    $mediaID = random(20);
    $media = new stdClass();
    $media->name = $name;
    $media->media = $file;
    $media->hash = sha1_file(MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $file);
    $database->library->$mediaID = $media;
    save();
}

function hasMedia($id)
{
    global $database;
    foreach ($database->library as $existent) {
        if ($existent === $id) return true;
    }
    return false;
}

// Time management

function addTime($time)
{
    global $database;
    if (!hasTime($time)) {
        $database->queue->$time = new stdClass();
        save();
    }
}

function removeTime($time)
{
    global $database;
    if (hasTime($time)) {
        unset($database->queue->$time);
        save();
    }
}

function hasTime($time)
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

function error($type, $message)
{
    result("errors", $type, $message);
}

function filter($source)
{
    // Filter inputs from XSS and other attacks
    $source = str_replace("<", "", $source);
    $source = str_replace(">", "", $source);
    return $source;
}

function random($length)
{
    $current = str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")[0];
    if ($length > 0) {
        return $current . random($length - 1);
    }
    return "";
}

function result($type, $key, $value)
{
    global $result;
    if (!isset($result->bell)) $result->bell = new stdClass();
    if (!isset($result->bell->$type)) $result->bell->$type = new stdClass();
    $result->bell->$type->$key = $value;
}

function save()
{
    global $database;
    file_put_contents(DATABASE, json_encode($database));
}