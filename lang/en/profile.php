<?php

/*
 | The profile of the signed in user - including the language of the app, which
 | is picked here (see App\Support\Locales).
 */

return [
    'title' => 'Profile',
    'subtitle' => 'Your details and your profile picture',
    'avatar' => 'Profile picture',
    'avatar_preview' => 'Preview',
    'choose_image' => 'Choose an image',
    'image_hint' => 'JPG, PNG or WebP · up to 10 MB, larger pictures are shrunk',
    'uploading' => 'Uploading …',
    'remove_avatar_title' => 'Remove the profile picture?',
    'remove_avatar_message' => 'Your image is deleted – your initials will be shown again afterwards.',
    'details' => 'Details',
    'password_heading' => 'Change the password',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'password_confirmation' => 'Repeat the new password',
    'language_heading' => 'Language',
    'language_hint' => 'The whole app is shown in this language afterwards.',
    'language_saved' => 'Language changed.',

    // The installed app (PWA): the section sits right below the language and
    // only shows up when the browser offers an installation or an iPhone needs
    // the way over the share menu (resources/js/app.js).
    'install_heading' => 'Install the app',
    'install_hint' => 'Coolventory also runs from the home screen – without the address bar and with the snowflake icon.',
    'install_ios' => 'Tap “Share” at the bottom, then “Add to Home Screen”.',
    'install_button' => 'Add to Home Screen',
    'saved' => 'Profile saved.',
    'password_changed' => 'Password changed.',
    'image_saved' => 'Profile picture saved.',
    'image_removed' => 'Profile picture removed.',
    'image_failed' => 'The image could not be saved.',
    'image_invalid' => 'Please choose a picture in JPG, PNG or WebP format.',
    'image_too_large' => 'The picture is larger than 10 MB. Please choose a smaller one.',

    // Legal: the imprint, the privacy policy and the terms of use
    // (resources/views/legal/*) and the deletion of the account, which fulfils
    // Art. 17 GDPR.
    'legal_heading' => 'Legal',
    'legal_hint' => 'Imprint, privacy policy and terms of use.',
    'delete_heading' => 'Delete account',
    'delete_hint' => 'Removes your account, your profile picture and every freezer you created, together with its contents. This cannot be undone.',
    'delete_password' => 'Confirm password',
    'delete_button' => 'Delete account for good',
    'delete_confirm_title' => 'Really delete the account?',
    'delete_confirm_message' => 'Your account and all data in it will be deleted – including your own freezers and their contents. Freezers that others shared with you remain. A deletion cannot be undone.',
    'account_deleted' => 'Your account has been deleted.',
];
