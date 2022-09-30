CSS 3 Modules
=============

.. _selectors3:

Selectors 3
-----------

.. currentmodule:: tinycss.css21

On :attr:`RuleSet.selector`, the :meth:`~.token_data.TokenList.as_css` method
can be used to serialize a selector back to an Unicode string.

    >>> import tinycss
    >>> stylesheet = tinycss.make_parser().parse_stylesheet(
    ...     'div.error, #root > section:first-letter { color: red }')
    >>> selector_string = stylesheet.rules[0].selector.as_css()
    >>> selector_string
    'div.error, #root > section:first-letter'

This string can be parsed by cssselect_. The parsed objects have information
about pseudo-elements and selector specificity.

.. _cssselect: http://packages.python.org/cssselect/

    >>> import cssselect
    >>> selectors = cssselect.parse(selector_string)
    >>> [s.specificity() for s in selectors]
    [(0, 1, 1), (1, 0, 2)]
    >>> [s.pseudo_element for s in selectors]
    [None, 'first-letter']

These objects can in turn be translated to XPath expressions. Note that
the translation ignores pseudo-elements, you have to account for them
somehow or reject selectors with pseudo-elements.

    >>> xpath = cssselect.HTMLTranslator().selector_to_xpath(selectors[1])
    >>> xpath
    "descendant-or-self::*[@id = 'root']/section"

Finally, the XPath expressions can be used with lxml_ to find the matching
elements.

    >>> from lxml import etree
    >>> compiled_selector = etree.XPath(xpath)
    >>> document = etree.fromstring('''<section id="root">
    ...   <section id="head">Title</section>
    ...   <section id="content">
    ...     Lorem <section id="sub-section">ipsum</section>
    ...   </section>
    ... </section>''')
    >>> [el.get('id') for el in compiled_selector(document)]
    ['head', 'content']

.. _lxml: http://lxml.de/xpathxslt.html#xpath

Find more details in the `cssselect documentation`_.

.. _cssselect documentation: http://packages.python.org/cssselect/


.. module:: tinycss.color3

Color 3
-------

This module implements parsing for the *<color>* values, as defined in
`CSS 3 Color <http://www.w3.org/TR/css3-color/>`_.

The (deprecated) CSS2 system colors are not supported, but you can
easily test for them if you want as they are simple ``IDENT`` tokens.
For example::

    if token.type == 'IDENT' and token.value == 'ButtonText':
        return ...

All other values types *are* supported:

* Basic, extended (X11) and transparent color keywords;
* 3-digit and 6-digit hexadecimal notations;
* ``rgb()``, ``rgba()``, ``hsl()`` and ``hsla()`` functional notations.
* ``currentColor``

This module does not integrate with a parser class. Instead, it provides
a function that can parse tokens as found in :attr:`.css21.Declaration.value`,
for example.

.. autofunction:: parse_color
.. autofunction:: parse_color_string
.. autoclass:: RGBA


.. module:: tinycss.page3

Paged Media 3
-------------

.. autoclass:: CSSPage3Parser
.. autoclass:: MarginRule


.. module:: tinycss.fonts3

Fonts 3
-------

.. autoclass:: CSSFonts3Parser
.. autoclass:: FontFaceRule
.. autoclass:: FontFeatureValuesRule
.. autoclass:: FontFeatureRule


Other CSS modules
-----------------

To add support for new CSS syntax, see :ref:`extending`.
