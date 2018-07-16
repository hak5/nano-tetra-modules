.. _extending:

Extending the parser
====================

Modules such as :mod:`.page3` extend the CSS 2.1 parser to add support for
CSS 3 syntax.
They do so by sub-classing :class:`.css21.CSS21Parser` and overriding/extending
some of its methods. If fact, the parser is made of methods in a class
(rather than a set of functions) solely to enable this kind of sub-classing.

tinycss is designed to enable you to have parser subclasses outside of
tinycss, without monkey-patching. If however the syntax you added is for a
W3C specification, consider including your subclass in a new tinycss module
and send a pull request: see :ref:`hacking`.


.. currentmodule:: tinycss.css21

Example: star hack
------------------

.. _star hack: https://en.wikipedia.org/wiki/CSS_filter#Star_hack

The `star hack`_ uses invalid declarations that are only parsed by some
versions of Internet Explorer. By default, tinycss ignores invalid
declarations and logs an error.

    >>> from tinycss.css21 import CSS21Parser
    >>> css = '#elem { width: [W3C Model Width]; *width: [BorderBox Model]; }'
    >>> stylesheet = CSS21Parser().parse_stylesheet(css)
    >>> stylesheet.errors
    [ParseError('Parse error at 1:35, expected a property name, got DELIM',)]
    >>> [decl.name for decl in stylesheet.rules[0].declarations]
    ['width']

If for example a minifier based on tinycss wants to support the star hack,
it can by extending the parser::

    >>> class CSSStarHackParser(CSS21Parser):
    ...     def parse_declaration(self, tokens):
    ...         has_star_hack = (tokens[0].type == 'DELIM' and tokens[0].value == '*')
    ...         if has_star_hack:
    ...             tokens = tokens[1:]
    ...         declaration = super(CSSStarHackParser, self).parse_declaration(tokens)
    ...         declaration.has_star_hack = has_star_hack
    ...         return declaration
    ...
    >>> stylesheet = CSSStarHackParser().parse_stylesheet(css)
    >>> stylesheet.errors
    []
    >>> [(d.name, d.has_star_hack) for d in stylesheet.rules[0].declarations]
    [('width', False), ('width', True)]

This class extends the :meth:`~CSS21Parser.parse_declaration` method.
It removes any ``*`` delimeter :class:`~.token_data.Token` at the start of
a declaration, and adds a ``has_star_hack`` boolean attribute on parsed
:class:`Declaration` objects: ``True`` if a ``*`` was removed, ``False`` for
“normal” declarations.


Parser methods
--------------

In addition to methods of the user API (see :ref:`parsing`), here
are the methods of the CSS 2.1 parser that can be overriden or extended:

.. automethod:: CSS21Parser.parse_rules
.. automethod:: CSS21Parser.read_at_rule
.. automethod:: CSS21Parser.parse_at_rule
.. automethod:: CSS21Parser.parse_media
.. automethod:: CSS21Parser.parse_page_selector
.. automethod:: CSS21Parser.parse_declarations_and_at_rules
.. automethod:: CSS21Parser.parse_ruleset
.. automethod:: CSS21Parser.parse_declaration_list
.. automethod:: CSS21Parser.parse_declaration
.. automethod:: CSS21Parser.parse_value_priority

Unparsed at-rules
-----------------

.. autoclass:: AtRule


.. module:: tinycss.parsing

Parsing helper functions
------------------------

The :mod:`tinycss.parsing` module contains helper functions for parsing
tokens into a more structured form:

.. autofunction:: strip_whitespace
.. autofunction:: split_on_comma
.. autofunction:: validate_value
.. autofunction:: validate_block
.. autofunction:: validate_any
