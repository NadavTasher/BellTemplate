<?php
include "../backend/bell/api.php";

// Template replacement constants
const ADMINISTRATOR_ACCOUNT_NAME = "Administrator";
const ADMINISTRATOR_ACCOUNT_PASSWORD = "BellTemplateDefaultPassword";
const DEFAULT_PRESET = "Preset 1";

accounts_register(ADMINISTRATOR_ACCOUNT_NAME, ADMINISTRATOR_ACCOUNT_PASSWORD);
accounts_save();

bell_preset_add(DEFAULT_PRESET);
bell_preset_set(DEFAULT_PRESET);
bell_save();

unlink(__FILE__);