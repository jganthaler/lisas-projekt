<?php

/*
 | Both sides of the freezer: the closed start page with the list of all
 | freezers and the opened one where the drawers are managed.
 |
 | "default_name" and "drawer_name" are the names a brand new freezer and its
 | drawers get - they are stored, so they carry the language of the moment the
 | account was created.
 |
 | "summary" is filled with the two counters of common.php.
 */

return [
    'title' => 'Freezer',
    'open_title' => 'Freezer open',
    'back' => 'Back',
    'close_aria' => 'Close the freezer',
    'default_name' => 'My freezer',
    'drawer_name' => 'Drawer :position',
    'empty' => 'Nothing is frozen in here yet.',
    'summary' => ':items in :drawers',
    'mine' => 'My freezers',
    'add_aria' => 'Add freezer',
    'add_cancel_aria' => 'Cancel creating',
    'new_label' => 'New freezer',
    'new_placeholder' => 'e.g. the chest freezer in the cellar',
    'rename_label' => 'Name of the freezer',
    'rename_aria' => 'Rename the freezer',
    'delete_aria' => 'Delete the freezer',
    'delete_confirm_title' => 'Delete freezer?',
    'delete_confirm_message' => '“:name” disappears with all drawers and items for good.',
    'owned' => 'Your freezer',
    'shared' => 'Shared with you',
    'active' => 'active',
    'drawers_heading' => 'Drawers',
    'manage_drawers' => 'Edit drawers',
    'name_required' => 'Please give the freezer a name.',
    'created' => '“:name” has been created.',
    'renamed' => 'Freezer renamed.',
    'deleted' => '“:name” was deleted with all drawers and items.',
    'new_drawer' => 'New drawer',
    'new_drawer_placeholder' => 'e.g. vegetables',
    'add_drawer_aria' => 'Add drawer',
    'rename_drawer_label' => 'Name of the drawer',
    'rename_drawer_aria' => 'Rename the drawer',
    'delete_drawer_aria' => 'Delete the drawer',
    'delete_drawer_confirm_title' => 'Delete drawer?',
    'delete_drawer_confirm_message' => '“:name” is removed. That only works while it is empty.',
    'no_drawers' => 'No drawer created yet.',
    'drawer_added' => 'Drawer added.',
    'drawer_renamed' => 'Drawer renamed.',
    'drawer_deleted' => 'Drawer deleted.',
    'drawer_name_required' => 'Please give the drawer a name.',
    'drawer_not_empty' => 'There are still items in “:name”. Please remove them first.',
];
