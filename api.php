<?php
// This file should be placed at the root of your web directory
// It will redirect requests to the appropriate API version

// Redirect to the current API version
header('Location: /v1/random');
exit;
?> 