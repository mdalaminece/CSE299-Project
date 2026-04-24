<?php
require_once __DIR__ . '/bootstrap.php';

logout_user();
flash('success', 'You have been logged out.');
redirect('index.php');
