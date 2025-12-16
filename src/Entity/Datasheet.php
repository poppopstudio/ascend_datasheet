<?php

namespace Drupal\ascend_datasheet\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\profile\Entity\Profile;
use Drupal\user\EntityOwnerTrait;

/**
 * Provides the Datasheet entity.
 *
 * @ContentEntityType(
 *   id = "datasheet",
 *   label = @Translation("Datasheet"),
 *   label_collection = @Translation("Datasheets"),
 *   label_singular = @Translation("datasheet"),
 *   label_plural = @Translation("datasheets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count datasheet",
 *     plural = "@count datasheets",
 *   ),
 *   base_table = "datasheet",
 *   revision_table = "datasheet_revision",
 *   show_revision_ui = TRUE,
 *   collection_permission = "access datasheet overview",
 *   handlers = {
 *     "access" = "Drupal\ascend_datasheet\Entity\Handler\DatasheetAccess",
 *     "route_provider" = {
 *       "html" = \Drupal\entity_admin_handlers\PlainBundleEntity\PlainBundleHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *     "form" = {
 *       "default" = "Drupal\ascend_datasheet\Form\DatasheetForm",
 *       "edit" = "Drupal\ascend_datasheet\Form\DatasheetForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "list_builder" = "Drupal\ascend_datasheet\Entity\Handler\DatasheetListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *   },
 *   admin_permission = "administer datasheet entities",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "datasheet_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   field_ui_base_route = "entity.datasheet.field_ui_base",
 *   links = {
 *     "add-form" = "/datasheet/add",
 *     "canonical" = "/datasheet/{datasheet}",
 *     "collection" = "/admin/content/datasheet",
 *     "delete-form" = "/datasheet/{datasheet}/delete",
 *     "edit-form" = "/datasheet/{datasheet}/edit",
 *     "field-ui-base" = "/admin/structure/datasheet",
 *     "version-history" = "/admin/structure/datasheet/{datasheet}/revisions",
 *     "revision" = "/admin/structure/datasheet/{datasheet}/revisions/{datasheet_revision}/view",
 *     "revision-revert-form" = "/admin/structure/datasheet/{datasheet}/revisions/{datasheet_revision}/revert",
 *     "revision-delete-form" = "/admin/structure/datasheet/{datasheet}/revisions/{datasheet_revision}/delete",
 *   },
 * )
 */
class Datasheet extends EditorialContentEntityBase implements DatasheetInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t("Datasheet name"))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -5])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setLabel(t("Published"))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t("Authored on"))
      ->setDescription(t("The date & time that the datasheet was created."))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t("Changed"))
      ->setDescription(t("The time that the datasheet was last edited."))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setComputed(TRUE);

    return $fields;
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update auditor's working datasheet after save.
    $this->updateAuditorWorkingDatasheet();
  }

  /**
   * Update the associated auditor's working datasheet if not set.
   */
  protected function updateAuditorWorkingDatasheet() {

    // Updates auditors assigned to this datasheet IF they have a blank/no profile.
    $auditor_ids = array_column(
      $this->get('ascend_sch_auditor')->getValue(),
      'target_id'
    );

    if (empty($auditor_ids)) {
      return;
    }

    foreach ($auditor_ids as $auditor_id) {
      // Attempt to load this auditor's profile(s).
      $auditor_profiles = \Drupal::entityTypeManager()
        ->getStorage('profile')
        ->loadByProperties([
          'uid' => $auditor_id,
          'type' => 'auditor'
        ]);

      // If the auditor doesn't have a profile we can safely create one.
      if (empty($auditor_profiles)) {

        $auditor_profile = Profile::create([
          'type' => 'auditor',
          'uid' => $auditor_id,
          'ascend_p_datasheet' => ['target_id' => $this->id()]
        ]);

        $auditor_profile->setDefault(TRUE);
        $auditor_profile->save();

        // Show on-screen message informing user what's been done.
        $auditor_name = \Drupal::entityTypeManager()->getStorage('user')->load($auditor_id)->getDisplayName();

        $message = 'Created a profile for auditor @auditor_name (id: @auditor_id) and set their working datasheet to @datasheet_name (id: @datasheet_id).';
        $context = [
          '@datasheet_name' => $this->label(),
          '@datasheet_id' => $this->id(),
          '@auditor_id' => $auditor_id,
          '@auditor_name' => $auditor_name
        ];
        \Drupal::messenger()->addMessage(t($message, $context));

        // If condition was met, we don't need to do anything lower than here.
        continue;
      }

      // Number of auditor's profiles should be 1 here, get the 'first' profile.
      $profile = reset($auditor_profiles);

      // If the auditor's profile has no working datasheet set (datasheet ID is 0).
      if ($profile->ascend_p_datasheet->target_id == 0) {

        // Set the datasheet ID on the profile and save it.
        $profile->set('ascend_p_datasheet', ['target_id' => $this->id()]);
        $profile->save();

        // Show on-screen message informing user what's been done.
        $auditor_name = \Drupal::entityTypeManager()->getStorage('user')->load($auditor_id)->getDisplayName();

        $message = 'Updated profile for auditor @auditor_name (id: @auditor_id), set their working datasheet to @datasheet_name (id: @datasheet_id).';
        $context = [
          '@datasheet_name' => $this->label(),
          '@datasheet_id' => $this->id(),
          '@auditor_id' => $auditor_id,
          '@auditor_name' => $auditor_name
        ];
        \Drupal::messenger()->addMessage(t($message, $context));
      }
    }
  }
}
