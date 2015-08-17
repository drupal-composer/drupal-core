<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\HtmlTag.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\SafeString;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element for any HTML tag, with properties and value.
 *
 * @RenderElement("html_tag")
 */
class HtmlTag extends RenderElement {

  /**
   * Void elements do not contain values or closing tags.
   * @see http://www.w3.org/TR/html5/syntax.html#syntax-start-tag
   * @see http://www.w3.org/TR/html5/syntax.html#void-elements
   */
  static protected $voidElements = array(
    'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
    'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr',
  );

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderConditionalComments'),
        array($class, 'preRenderHtmlTag'),
      ),
      '#attributes' => array(),
      '#value' => NULL,
    );
  }

  /**
   * Pre-render callback: Renders a generic HTML tag with attributes into #markup.
   *
   * Note: It is the caller's responsibility to sanitize #value_prefix and
   * #value_suffix. They are not filtered by this function.
   *
   * @param array $element
   *   An associative array containing:
   *   - #tag: The tag name to output. Typical tags added to the HTML HEAD:
   *     - meta: To provide meta information, such as a page refresh.
   *     - link: To refer to stylesheets and other contextual information.
   *     - script: To load JavaScript.
   *     The value of #tag is escaped.
   *   - #attributes: (optional) An array of HTML attributes to apply to the
   *     tag. The attributes are escaped, see \Drupal\Core\Template\Attribute.
   *   - #value: (optional) A string containing tag content, such as inline
   *     CSS. The value of #value will be XSS admin filtered if it is not safe.
   *   - #value_prefix: (optional) A string to prepend to #value, e.g. a CDATA
   *     wrapper prefix. The value of #value_prefix cannot be filtered and is
   *     assumed to be safe.
   *   - #value_suffix: (optional) A string to append to #value, e.g. a CDATA
   *     wrapper suffix. The value of #value_suffix cannot be filtered and is
   *     assumed to be safe.
   *   - #noscript: (optional) If TRUE, the markup (including any prefix or
   *     suffix) will be wrapped in a <noscript> element. (Note that passing
   *     any non-empty value here will add the <noscript> tag.)
   *
   * @return array
   */
  public static function preRenderHtmlTag($element) {
    $attributes = isset($element['#attributes']) ? new Attribute($element['#attributes']) : '';

    // An HTML tag should not contain any special characters. Escape them to
    // ensure this cannot be abused.
    $escaped_tag = HtmlUtility::escape($element['#tag']);
    $markup = '<' . $escaped_tag . $attributes;
    // Construct a void element.
    if (in_array($element['#tag'], self::$voidElements)) {
      $markup .= " />\n";
    }
    // Construct all other elements.
    else {
      $markup .= '>';
      if (isset($element['#value_prefix'])) {
        $markup .= $element['#value_prefix'];
      }
      $markup .= SafeMarkup::isSafe($element['#value']) ? $element['#value'] : Xss::filterAdmin($element['#value']);
      if (isset($element['#value_suffix'])) {
        $markup .= $element['#value_suffix'];
      }
      $markup .= '</' . $escaped_tag . ">\n";
    }
    if (!empty($element['#noscript'])) {
      $markup = "<noscript>$markup</noscript>";
    }
    $element['#markup'] = SafeString::create($markup);
    return $element;
  }

  /**
   * Pre-render callback: Renders #browsers into #prefix and #suffix.
   *
   * @param array $element
   *   A render array with a '#browsers' property. The '#browsers' property can
   *   contain any or all of the following keys:
   *   - 'IE': If FALSE, the element is not rendered by Internet Explorer. If
   *     TRUE, the element is rendered by Internet Explorer. Can also be a string
   *     containing an expression for Internet Explorer to evaluate as part of a
   *     conditional comment. For example, this can be set to 'lt IE 7' for the
   *     element to be rendered in Internet Explorer 6, but not in Internet
   *     Explorer 7 or higher. Defaults to TRUE.
   *   - '!IE': If FALSE, the element is not rendered by browsers other than
   *     Internet Explorer. If TRUE, the element is rendered by those browsers.
   *     Defaults to TRUE.
   *   Examples:
   *   - To render an element in all browsers, '#browsers' can be left out or set
   *     to array('IE' => TRUE, '!IE' => TRUE).
   *   - To render an element in Internet Explorer only, '#browsers' can be set
   *     to array('!IE' => FALSE).
   *   - To render an element in Internet Explorer 6 only, '#browsers' can be set
   *     to array('IE' => 'lt IE 7', '!IE' => FALSE).
   *   - To render an element in Internet Explorer 8 and higher and in all other
   *     browsers, '#browsers' can be set to array('IE' => 'gte IE 8').
   *
   * @return array
   *   The passed-in element with markup for conditional comments potentially
   *   added to '#prefix' and '#suffix'.
   */
  public static function preRenderConditionalComments($element) {
    $browsers = isset($element['#browsers']) ? $element['#browsers'] : array();
    $browsers += array(
      'IE' => TRUE,
      '!IE' => TRUE,
    );

    // If rendering in all browsers, no need for conditional comments.
    if ($browsers['IE'] === TRUE && $browsers['!IE']) {
      return $element;
    }

    // Determine the conditional comment expression for Internet Explorer to
    // evaluate.
    if ($browsers['IE'] === TRUE) {
      $expression = 'IE';
    }
    elseif ($browsers['IE'] === FALSE) {
      $expression = '!IE';
    }
    else {
      // The IE expression might contain some user input data.
      $expression = Xss::filterAdmin($browsers['IE']);
    }

    // If the #prefix and #suffix properties are used, wrap them with
    // conditional comment markup. The conditional comment expression is
    // evaluated by Internet Explorer only. To control the rendering by other
    // browsers, use either the "downlevel-hidden" or "downlevel-revealed"
    // technique. See http://en.wikipedia.org/wiki/Conditional_comment
    // for details.

    // Ensure what we are dealing with is safe.
    // This would be done later anyway in drupal_render().
    $prefix = isset($element['#prefix']) ? $element['#prefix'] : '';
    if ($prefix && !SafeMarkup::isSafe($prefix)) {
      $prefix = Xss::filterAdmin($prefix);
    }
    $suffix = isset($element['#suffix']) ? $element['#suffix'] : '';
    if ($suffix && !SafeMarkup::isSafe($suffix)) {
      $suffix = Xss::filterAdmin($suffix);
    }

    // We ensured above that $expression is either a string we created or is
    // admin XSS filtered, and that $prefix and $suffix are also admin XSS
    // filtered if they are unsafe. Thus, all these strings are safe.
    if (!$browsers['!IE']) {
      // "downlevel-hidden".
      $element['#prefix'] = SafeString::create("\n<!--[if $expression]>\n" . $prefix);
      $element['#suffix'] = SafeString::create($suffix . "<![endif]-->\n");
    }
    else {
      // "downlevel-revealed".
      $element['#prefix'] = SafeString::create("\n<!--[if $expression]><!-->\n" . $prefix);
      $element['#suffix'] = SafeString::create($suffix . "<!--<![endif]-->\n");
    }

    return $element;
  }

}
