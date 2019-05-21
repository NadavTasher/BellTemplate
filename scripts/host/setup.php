<?php
include "../backend/bell/api.php";

// Template replacement constants
const ADMINISTRATOR_ACCOUNT_NAME = "Administrator";
const ADMINISTRATOR_ACCOUNT_PASSWORD = "BellTemplateDefaultPassword";
const DEFAULT_PRESET = "BellTemplatePreset";

accounts_register(ADMINISTRATOR_ACCOUNT_NAME, ADMINISTRATOR_ACCOUNT_PASSWORD);
accounts_save();

bell_add_preset(DEFAULT_PRESET);
bell_set_preset(DEFAULT_PRESET);
bell_save();