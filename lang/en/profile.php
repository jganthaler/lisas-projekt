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
    'uploading' => 'Uploading …',
    'remove_avatar_title' => 'Remove the profile picture?',
    'remove_avatar_message' => 'Your image is deleted – your initials will be shown again afterwards.',
    'details' => 'Details',
    // The confirmed e-mail address is the name of the account: the profile only
    // shows it, there is no field to change it anymore (see
    // App\Livewire\Profile\ProfilePage::updateProfile()).
    'email_locked' => 'Your address is confirmed and can therefore not be changed.',
    'password_heading' => 'Change the password',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'password_confirmation' => 'Repeat the new password',
    'language_heading' => 'Language',
    'language_saved' => 'Language changed.',

    // The installed app (PWA): the section sits right below the language and
    // only shows up when the browser offers an installation or an iPhone needs
    // the way over the share menu (resources/js/app.js).
    'install_heading' => 'Install the app',
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
    'delete_heading' => 'Delete account',
    'delete_hint' => 'Removes your account, your profile picture and every freezer you created, together with its contents. This cannot be undone.',
    'delete_password' => 'Confirm password',
    'delete_button' => 'Delete account for good',
    'delete_confirm_title' => 'Really delete the account?',
    'delete_confirm_message' => 'Your account and all data in it will be deleted – including your own freezers and their contents. Freezers that others shared with you remain. A deletion cannot be undone.',
    'account_deleted' => 'Your account has been deleted.',
];
