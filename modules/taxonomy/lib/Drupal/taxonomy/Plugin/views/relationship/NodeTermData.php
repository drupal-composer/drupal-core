<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\relationship\NodeTermData.
 */

namespace Drupal\taxonomy\Plugin\views\relationship;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Relationship handler to return the taxonomy terms of nodes.
 *
 * @ingroup views_relationship_handlers
 *
 * @Plugin(
 *   id = "node_term_data",
 *   module = "taxonomy"
 * )
 */
class NodeTermData extends RelationshipPluginBase  {

  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['vids'] = array('default' => array());
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    $options = array();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vocabularies'],
      '#description' => t('Choose which vocabularies you wish to relate. Remember that every term found will create a new record, so this relationship is best used on just one vocabulary that has only one term per node.'),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $this->ensureMyTable();

    $def = $this->definition;
    $def['table'] = 'taxonomy_term_data';

    if (!array_filter($this->options['vids'])) {
      $taxonomy_index = $this->query->add_table('taxonomy_index', $this->relationship);
      $def['left_table'] = $taxonomy_index;
      $def['left_field'] = 'tid';
      $def['field'] = 'tid';
      $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
    }
    else {
      // If vocabularies are supplied join a subselect instead
      $def['left_table'] = $this->tableAlias;
      $def['left_field'] = 'nid';
      $def['field'] = 'nid';
      $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
      $def['adjusted'] = TRUE;

      $query = db_select('taxonomy_term_data', 'td');
      $query->addJoin($def['type'], 'taxonomy_index', 'tn', 'tn.tid = td.tid');
      $query->condition('td.vid', array_filter($this->options['vids']));
      $query->addTag('term_access');
      $query->fields('td');
      $query->fields('tn', array('nid'));
      $def['table formula'] = $query;
    }

    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard', $def);

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, 'taxonomy_term_data', $this->relationship);
  }

}
