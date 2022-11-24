# TYPO3 HTML Sanitizer notices for upgrading

## v2.1.0

* deprecated `\TYPO3\HtmlSanitizer\Behavior\NodeException::withNode(?DOMNode $node)`,
  use `\TYPO3\HtmlSanitizer\Behavior\NodeException::withDomNode(?DOMNode $domNode)` instead
* deprecated `\TYPO3\HtmlSanitizer\Behavior\NodeException::getNode()`,
  use `\TYPO3\HtmlSanitizer\Behavior\NodeException::getDomNode()` instead
* deprecated property `\TYPO3\HtmlSanitizer\Sanitizer::$root`, superfluous - don't use it anymore
