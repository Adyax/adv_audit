<?php

namespace Drupal\adv_audit\Message;


interface AuditMessagesStorageInterface {
  const MSG_TYPE_DESCRIPTION = 'description';
  const MSG_TYPE_ACTIONS = 'actions';
  const MSG_TYPE_IMPACTS = 'impacts';
  const MSG_TYPE_FAIL = 'fail';
  const MSG_TYPE_SUCCESS = 'success';
  const MSG_TYPE_HELP = 'help';


  const COLLECTION_NAME = 'messages';

  public function set($plugin_id, $type, $string);
  public function get($plugin_id, $type);

  /**
   * Get translated string object.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param $type
   *   The message type.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source
   *     string belongs to. See the
   *     @link i18n Internationalization topic @endlink for more information
   *     about string contexts.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   */
  public function getTranslated($plugin_id, $type, $options);

  /**
   * Replaces placeholders in a string with values.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param $type
   *   The message type.
   * @param array $args
   *   An associative array of replacements. Each array key should be the same
   *   as a placeholder in $string. The corresponding value should be a string
   *   or an object that implements
   *   \Drupal\Component\Render\MarkupInterface. The value replaces the
   *   placeholder in $string. Sanitization and formatting will be done before
   *   replacement. The type of sanitization and formatting depends on the first
   *   character of the key:
   *   - @variable: When the placeholder replacement value is:
   *     - A string, the replaced value in the returned string will be sanitized
   *       using \Drupal\Component\Utility\Html::escape().
   *     - A MarkupInterface object, the replaced value in the returned string
   *       will not be sanitized.
   *     - A MarkupInterface object cast to a string, the replaced value in the
   *       returned string be forcibly sanitized using
   *       \Drupal\Component\Utility\Html::escape().
   *       @code
   *         $this->placeholderFormat('This will force HTML-escaping of the replacement value: @text', ['@text' => (string) $safe_string_interface_object));
   *       @endcode
   *     Use this placeholder as the default choice for anything displayed on
   *     the site, but not within HTML attributes, JavaScript, or CSS. Doing so
   *     is a security risk.
   *   - %variable: Use when the replacement value is to be wrapped in <em>
   *     tags.
   *     A call like:
   *     @code
   *       $string = "%output_text";
   *       $arguments = ['%output_text' => 'text output here.'];
   *       $this->placeholderFormat($string, $arguments);
   *     @endcode
   *     makes the following HTML code:
   *     @code
   *       <em class="placeholder">text output here.</em>
   *     @endcode
   *     As with @variable, do not use this within HTML attributes, JavaScript,
   *     or CSS. Doing so is a security risk.
   *   - :variable: Return value is escaped with
   *     \Drupal\Component\Utility\Html::escape() and filtered for dangerous
   *     protocols using UrlHelper::stripDangerousProtocols(). Use this when
   *     using the "href" attribute, ensuring the attribute value is always
   *     wrapped in quotes:
   *     @code
   *     // Secure (with quotes):
   *     $this->placeholderFormat('<a href=":url">@variable</a>', [':url' => $url, '@variable' => $variable]);
   *     // Insecure (without quotes):
   *     $this->placeholderFormat('<a href=:url>@variable</a>', [':url' => $url, '@variable' => $variable]);
   *     @endcode
   *     When ":variable" comes from arbitrary user input, the result is secure,
   *     but not guaranteed to be a valid URL (which means the resulting output
   *     could fail HTML validation). To guarantee a valid URL, use
   *     Url::fromUri($user_input)->toString() (which either throws an exception
   *     or returns a well-formed URL) before passing the result into a
   *     ":variable" placeholder.
   *
   * @return string
   *   A formatted HTML string with the placeholders replaced.
   *
   * @see \Drupal\Core\StringTranslation\TranslatableMarkup
   * @see \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
   * @see \Drupal\Core\Url::fromUri()
   */
  public function replacePlaceholder($plugin_id, $type, $args);


}