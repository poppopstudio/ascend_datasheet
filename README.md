# Ascend datasheet

Recipe/module to enable datasheet functionality for Ascend.

## Installation

### Add the repository to composer

```composer
{
    "type": "vcs",
    "url": "git@github.com:poppopstudio/ascend_datasheet.git"
},
```

### Add the EVA patch

```composer
"drupal/eva": {
    "Missing context in EVA view object -https://www.drupal.org/project/eva/issues/3565913": "patches/3565913-2-eva--entity-context.patch"
}
```

### Install the module and dependencies via the recipe

From project root, `drush recipe modules/contrib/ascend_datasheet/recipes/datasheet`

### Update the AP summary view

- Open the AP summary report view config.
- In the header or footer, add Rendered entity - School
- Select Use replacement tokens from the first row
- Select view mode "Datasheet only"
- In the School ID field, copy and paste "{{ raw_arguments.school }}" from the
  replacement patterns
- Click apply, and save the view, export the config and deploy
