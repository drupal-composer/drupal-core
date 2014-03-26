<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListTextItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_text' field type.
 *
 * @FieldType(
 *   id = "list_text",
 *   label = @Translation("List (text)"),
 *   description = @Translation("This field stores text values from a list of allowed 'value => label' pairs, i.e. 'US States': IL => Illinois, IA => Iowa, IN => Indiana."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class ListTextItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $constraints = array('Length' => array('max' => 255));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->setConstraints($constraints);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.');
    $description .= '</p>';
    $description .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (drupal_strlen($option) > 255) {
      return t('Allowed values list: each key must be a string at most 255 characters long.');
    }
  }

}
