# coding: utf8
"""
    Test suite for tinycss
    ----------------------

    :copyright: (c) 2012 by Simon Sapin.
    :license: BSD, see LICENSE for more details.
"""


from __future__ import unicode_literals


def assert_errors(errors, expected_errors):
    """Test not complete error messages but only substrings."""
    assert len(errors) == len(expected_errors)
    for error, expected in zip(errors, expected_errors):
        assert expected in str(error)
