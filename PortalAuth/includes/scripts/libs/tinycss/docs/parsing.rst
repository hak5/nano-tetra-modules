Parsing with tinycss
====================

.. highlight:: python

Quickstart
----------

Import *tinycss*, make a parser object with the features you want,
and parse a stylesheet:

.. doctest::

    >>> import tinycss
    >>> parser = tinycss.make_parser('page3')
    >>> stylesheet = parser.parse_stylesheet_bytes(b'''@import "foo.css";
    ...     p.error { color: red }  @lorem-ipsum;
    ...     @page tables { size: landscape }''')
    >>> stylesheet.rules
    [<ImportRule 1:1 foo.css>, <RuleSet at 2:5 p.error>, <PageRule 3:5 ('tables', None)>]
    >>> stylesheet.errors
    [ParseError('Parse error at 2:29, unknown at-rule in stylesheet context: @lorem-ipsum',)]

You’ll get a :class:`~tinycss.css21.Stylesheet` object which contains
all the parsed content as well as a list of encountered errors.


Parsers
-------

Parsers are subclasses of :class:`tinycss.css21.CSS21Parser`. Various
subclasses add support for more syntax. You can choose which features to
enable by making a new parser class with multiple inheritance, but there
is also a convenience function to do that:

.. module:: tinycss

.. autofunction:: make_parser


.. module:: tinycss.css21
.. _parsing:

Parsing a stylesheet
~~~~~~~~~~~~~~~~~~~~

Parser classes have three different methods to parse CSS stylesheet,
depending on whether you have a file, a byte string, or an Unicode string.

.. autoclass:: CSS21Parser
    :members: parse_stylesheet_file, parse_stylesheet_bytes, parse_stylesheet


Parsing a ``style`` attribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. automethod:: CSS21Parser.parse_style_attr


Parsed objects
--------------

These data structures make up the results of the various parsing methods.

.. autoclass:: tinycss.parsing.ParseError()
.. autoclass:: Stylesheet()

.. note::
    All subsequent objects have :obj:`line` and :obj:`column` attributes (not
    repeated every time fore brevity) that indicate where in the CSS source
    this object was read.

.. autoclass:: RuleSet()
.. autoclass:: ImportRule()
.. autoclass:: MediaRule()
.. autoclass:: PageRule()
.. autoclass:: Declaration()


Tokens
------

Some parts of a stylesheet (such as selectors in CSS 2.1 or property values)
are not parsed by tinycss. They appear as tokens instead.

.. module:: tinycss.token_data

.. autoclass:: TokenList()
    :member-order: bysource
    :members:
.. autoclass:: Token()
    :members:
.. autoclass:: tinycss.speedups.CToken()
.. autoclass:: ContainerToken()
    :members:

.. autoclass:: FunctionToken()
