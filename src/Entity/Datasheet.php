<?php

namespace Drupal\ascend_datasheet\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Provides the Datasheet entity.
 *
 * @ContentEntityType(
 *   id = "datasheet",
 *   label = @Translation("Datasheet"),
 *   label_collection = @Translation("Datasheets"),
 *   label_singular = @Translation("Datasheet"),
 *   label_plural = @Translation("Datasheets"),
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
 *       "html" = "Drupal\entity_admin_handlers\PlainBundleEntity\PlainBundleHtmlRouteProvider",
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
 *     "bundle" = "type",
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

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The bundle of the entity.'))
      ->setRequired(TRUE);

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

    // Might not want this here as school is not present on nat/loc records.

    $fields['school'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t("School"))
      ->setDescription(t("The datasheet's school."))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      // ->setRequired(TRUE)
      ->setCardinality(1)
      // ->setDefaultValueCallback('Drupal\ascend_audit\Entity\Audit::getDefaultSchool')
      ->setSetting('target_type', 'school')
      ->setSetting('handler', 'default:school')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
      ));

    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t("Year"))
      ->setDescription(t('Datasheet year in YY format (e.g. 24 for 2024/25).'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDefaultValueCallback('Drupal\ascend_audit\Entity\Audit::getDefaultYear')
      ->setSettings([
        'max_length' => 2,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'audit_year_formatter',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 0,
      ));

    return $fields;
  }

  /**
   * Use a computed label instead of storing titles.
   */
  public function label() {
    // $category_id = $this->get('category')->target_id ?? 'X'; // Probably needs work on the Xs!
    // $school_id = $this->get('school')->target_id ?? 'X';
    // $year = $this->get('year')->value ?? 'X';
    return "some string";
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

}
