<?php
require_once __DIR__ . '/../config/helpers.php';

/** LOGOUT — distrugge la sessione corrente. */

session_unset();
session_destroy();

rispondiSuccesso(['message' => 'Logout effettuato.']);
