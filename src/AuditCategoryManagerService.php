<?php

namespace Drupal\adv_audit;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class AuditCategoryManagerService.
 */
class AuditCategoryManagerService {

  use StringTranslationTrait;
  use RedirectDestinationTrait;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\adv_audit\Plugin\AdvAuditCheckManager definition.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckManager
   */
  protected $pluginManagerAdvAuditCheck;

  protected $categoryDefinitions;

  /**
   * Constructs a new AduitCategoryManagerService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckManager $plugin_manager_adv_audit_check) {
    $this->configFactory = $config_factory;
    $this->pluginManagerAdvAuditCheck = $plugin_manager_adv_audit_check;
    $this->categoryDefinitions = $this->configFactory->get('adv_audit.settings')->get('categories');
  }

  /**
   * Return definition properties for selected category.
   *
   * @param string $category_id
   *   The category id.
   *
   * @return array
   *   The definition values of the selected category.
   */
  public function getCategoryDefinition($category_id) {
    return isset($this->categoryDefinitions[$category_id]) ? $this->categoryDefinitions[$category_id] : [];
  }

  /**
   * Get category status by ID.
   *
   * @param string $id
   *   Machine name of category.
   *
   * @return mixed
   *   Return category status.
   */
  public function getStatus($id) {
    return $this->categoryDefinitions[$id]['status'];
  }

  /**
   * Return list of enabled categories.
   *
   * @return array
   *   Return list of categories.
   */
  public function getListOfCategories() {
    $list = [];
    foreach ($this->categoryDefinitions as $category_id => $category_definition) {
      if ($category_definition['status'] == 0) {
        continue;
      }
      $list[$category_id] = $category_definition['label'];
    }
    return $list;
  }

  /**
   * Handler callback for page '/admin/reports/adv-audit'.
   */
  public function buildCategoriesOverview() {
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'adv_audit/adv_audit.admin',
        ],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('List of available audit plugins:'),
      ],
    ];

    foreach ($this->categoryDefinitions as $category_id => $definition) {
      $build[$category_id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@label (%edit_link)', [
          '@label' => $definition['label'],
          '%edit_link' => Link::createFromRoute('Edit', 'adv_audit.category.settings_form', ['category_id' => $category_id])->toString(),
        ]),
        '#attributes' => [
          'class' => ['category-wrapper'],
        ],
      ];
      foreach ($this->pluginManagerAdvAuditCheck->getPluginsByCategoryFilter($category_id) as $plugin) {
        $pid = $plugin['id'];
        $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($pid);
        $build[$category_id][$pid] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['plugin-wrapper'],
          ],
          'status' => [
            '#type' => 'checkbox',
            '#title' => $plugin['label'],
            '#checked' => $plugin_instance->isEnabled() ? TRUE : FALSE,
            '#attributes' => [
              'disabled' => 'disabled',
            ],
          ],
          'edit' => [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromRoute('adv_audit.plugin.settings', ['plugin_id' => $pid], ['query' => [$this->getDestinationArray()]]),
            '#attributes' => [
              'class' => ['edit', 'edit-checkpoint'],
            ],
          ],
          '#weight' => $plugin_instance->getWeight(),
        ];
      }
      uasort($build[$category_id], [SortArray::class, 'sortByWeightProperty']);

      if (empty($build[$category_id][$pid])) {
        $build[$category_id][$pid]['#markup'] = $this->t('There are no audit plugins available for this category.');
      }
    }
    return $build;
  }

  /**
   * Update category definition.
   *
   * @param string $category_id
   *   Machine name of category.
   * @param string $key
   *   Configuration key.
   * @param mixed $value
   *   New value.
   */
  public function updateCategoryDefinitionValue($category_id, $key, $value) {
    $config = $this->configFactory->getEditable('adv_audit.settings');
    $config->set('categories.' . $category_id . '.' . $key, $value)->save();
  }

}
