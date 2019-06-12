<?php

/**
 * Copyright (c) 2019 Nadav Tasher
 * https://github.com/NadavTasher/BellTemplate/
 **/

include_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "backend" . DIRECTORY_SEPARATOR . "bell" . DIRECTORY_SEPARATOR . "api.php";

// Template replacement constants
const ADMINISTRATOR_ACCOUNT_NAME = "Administrator";
const ADMINISTRATOR_ACCOUNT_PASSWORD = "BellTemplateDefaultPassword";
const DEFAULT_PRESET = "Preset 1";

accounts_load();
accounts_register(ADMINISTRATOR_ACCOUNT_NAME, ADMINISTRATOR_ACCOUNT_PASSWORD);
accounts_save();

bell_preset_add(DEFAULT_PRESET);
bell_preset_set(DEFAULT_PRESET);
bell_save();

unlink(__FILE__);