<?php

/*
 | Both sides of the freezer: the closed start page with the list of all
 | freezers and the opened one where the drawers are managed.
 |
 | "default_name" and "drawer_name" are the names a brand new freezer and its
 | drawers get - they are stored, so they carry the language of the moment the
 | account was created.
 */

return [
    'title' => 'Freezer',
    'open_title' => 'Freezer open',
    'back' => 'Back',
    'close_aria' => 'Close the freezer',
    'default_name' => 'My freezer',
    'drawer_name' => 'Drawer :position',
    'mine' => 'My freezers',
    /*
     | The field "My freezers" on the start page: its header carries the title,
     | the plus for another freezer and the menu ("⋮") of the active one, which
     | renames and deletes it. The arrow beside the name of the active freezer
     | opens the list of all of them.
     */
    'add' => 'Add freezer',
    'edit' => 'Edit freezer',
    'delete' => 'Delete freezer',
    'menu_aria' => 'More actions for “:name”',
    'new_label' => 'New freezer',
    'new_placeholder' => 'e.g. the chest freezer in the cellar',
    'rename_label' => 'Name of the freezer',
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

    /*
     | The menu of a drawer row ("⋮"): its two actions carry their own label, and
     | the button of the menu names the row it belongs to.
     */
    'rename_drawer' => 'Rename',
    'drawer_menu_aria' => 'More actions for “:name”',
    'delete_drawer_confirm_title' => 'Delete drawer?',
    'delete_drawer_confirm_message' => '“:name” is removed. That only works while it is empty.',
    'no_drawers' => 'No drawer created yet.',
    'drawer_added' => 'Drawer added.',
    'drawer_renamed' => 'Drawer renamed.',
    'drawer_deleted' => 'Drawer deleted.',
    'drawer_name_required' => 'Please give the drawer a name.',
    'drawer_not_empty' => 'There are still items in “:name”. Please remove them first.',
];
