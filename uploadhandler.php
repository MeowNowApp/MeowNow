<?php
    function is_local() {
        $allowed_ips = ['::1', '127.0.0.1'];
        if (strpos($_SERVER['REMOTE_ADDR'], '10.0.0.') === 0 || strpos($_SERVER['REMOTE_ADDR'], 'fd') === 0) {
            return true; // Local IPv4 and IPv6 ranges
        }
        return in_array($_SERVER['REMOTE_ADDR'], $allowed_ips);
    }

    if (!is_local()) {
        die("Access denied: Only available on local network.");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
        $upload_dir = './cats/';
        $allowed_types = ['image/jpeg', 'image/png'];

        $file_type = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            echo "Invalid file type. Only JPG and PNG are allowed.";
        } else {
            $image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name']));
            if ($image === false) {
                echo "Failed to process image.";
            } else {
                $file_name = uniqid('cat_') . '.jpg'; // Convert to JPG
                $file_path = $upload_dir . $file_name;

                if (imagejpeg($image, $file_path, 90)) {
                    echo "Image uploaded successfully: $file_name";
                } else {
                    echo "Error saving image.";
                }
                imagedestroy($image);
            }
        }
    }
    ?>
