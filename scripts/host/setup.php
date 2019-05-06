<?php
include "../backend/accounts/api.php";

// Template replacement constants
const ADMINISTRATOR_ACCOUNT_NAME = "Administrator";
const ADMINISTRATOR_ACCOUNT_PASSWORD = "BellTemplateDefaultPassword";
const DEFAULT_QUEUE = "BellTemplateQueue";
const RINGING_SCHEDULE = [0, 0];

register(ADMINISTRATOR_ACCOUNT_NAME, ADMINISTRATOR_ACCOUNT_PASSWORD);
foreach (RINGING_SCHEDULE as $ring) {
    addTime(HHMMtoMMMM($ring));
}
addQueue(DEFAULT_QUEUE);

function HHMMtoMMMM($hhmm)
{
    if (!is_numeric($hhmm)) $hhmm = intval($hhmm);
    return (($hhmm - $hhmm % 100) / 100) * 60 + $hhmm % 100;
}