<?php
include "../backend/accounts/api.php";

// Template replacement constants
const ADMINISTRATOR_ACCOUNT_NAME = "Administrator";
const ADMINISTRATOR_ACCOUNT_PASSWORD = "BellTemplateDefaultPassword";
const DEFAULT_PRESET = "BellTemplatePreset";
const RINGING_SCHEDULE = [0, 0];

accounts_register(ADMINISTRATOR_ACCOUNT_NAME, ADMINISTRATOR_ACCOUNT_PASSWORD);
accounts_save();
foreach (RINGING_SCHEDULE as $ring) {
    bell_add_time(HHMMtoMMMM($ring));
}

bell_add_preset(DEFAULT_PRESET);
bell_set_preset(DEFAULT_PRESET);

function HHMMtoMMMM($hhmm)
{
    if (!is_numeric($hhmm)) $hhmm = intval($hhmm);
    return (($hhmm - $hhmm % 100) / 100) * 60 + $hhmm % 100;
}