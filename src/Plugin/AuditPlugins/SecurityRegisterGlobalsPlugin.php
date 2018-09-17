<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Check Register Globals is enabled.
 *
 * @AuditPlugin(
 *   id = "register_globals_check",
 *   label = @Translation("PHP register globals"),
 *   category = "security",
 *   requirements = {},
 * )
 */
class SecurityRegisterGlobalsPlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $register_globals = trim(ini_get('register_globals'));
    if (!empty($register_globals) && strtolower($register_globals) != 'off') {
      $this->fail(NULL, [
        'issues' => [
          'register_globals_check' => [
            '@issue_title' => 'register_globals is enabled.',
          ],
        ],
        '%link' => Link::fromTextAndUrl($this->t('PHP documentation'),
          Url::fromUri('http://php.net/manual/en/security.globals.php'))->toString(),
      ]);
    }

    return $this->success();
  }

}
