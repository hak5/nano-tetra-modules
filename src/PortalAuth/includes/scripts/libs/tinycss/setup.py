import os.path
import re
import sys
from distutils.errors import (
    CCompilerError, DistutilsExecError, DistutilsPlatformError)
from setuptools import Extension, setup

try:
    from Cython.Distutils import build_ext
    import Cython.Compiler.Version
    CYTHON_INSTALLED = True
except ImportError:
    from distutils.command.build_ext import build_ext
    CYTHON_INSTALLED = False


ext_errors = (CCompilerError, DistutilsExecError, DistutilsPlatformError)
if sys.platform == 'win32' and sys.version_info > (2, 6):
    # 2.6's distutils.msvc9compiler can raise an IOError when failing to
    # find the compiler
    ext_errors += (IOError,)


class BuildFailed(Exception):
    pass


class ve_build_ext(build_ext):
    # This class allows C extension building to fail.

    def run(self):
        try:
            build_ext.run(self)
        except DistutilsPlatformError:
            raise BuildFailed

    def build_extension(self, ext):
        try:
            build_ext.build_extension(self, ext)
        except ext_errors:
            raise BuildFailed


ROOT = os.path.dirname(__file__)
with open(os.path.join(ROOT, 'tinycss', 'version.py')) as fd:
    VERSION = re.search("VERSION = '([^']+)'", fd.read()).group(1)

with open(os.path.join(ROOT, 'README.rst'), 'rb') as fd:
    README = fd.read().decode('utf8')


needs_pytest = {'pytest', 'test', 'ptr'}.intersection(sys.argv)
pytest_runner = ['pytest-runner'] if needs_pytest else []


def run_setup(with_extension):
    if with_extension:
        extension_path = os.path.join('tinycss', 'speedups')
        if CYTHON_INSTALLED:
            extension_path += '.pyx'
            print('Building with Cython %s.' % Cython.Compiler.Version.version)
        else:
            extension_path += '.c'
            if not os.path.exists(extension_path):
                print("WARNING: Trying to build without Cython, but "
                      "pre-generated '%s' does not seem to be available."
                      % extension_path)
            else:
                print('Building without Cython.')
        kwargs = dict(
            cmdclass=dict(build_ext=ve_build_ext),
            ext_modules=[Extension('tinycss.speedups',
                                   sources=[extension_path])],
        )
    else:
        kwargs = dict()

    setup(
        name='tinycss',
        version=VERSION,
        url='http://tinycss.readthedocs.io/',
        license='BSD',
        author='Simon Sapin',
        author_email='simon.sapin@exyr.org',
        description='tinycss is a complete yet simple CSS parser for Python.',
        long_description=README,
        classifiers=[
            'Development Status :: 4 - Beta',
            'Intended Audience :: Developers',
            'License :: OSI Approved :: BSD License',
            'Programming Language :: Python :: 2',
            'Programming Language :: Python :: 2.7',
            'Programming Language :: Python :: 3',
            'Programming Language :: Python :: 3.3',
            'Programming Language :: Python :: 3.4',
            'Programming Language :: Python :: 3.5',
            'Programming Language :: Python :: Implementation :: CPython',
            'Programming Language :: Python :: Implementation :: PyPy',
        ],
        setup_requires=pytest_runner,
        tests_require=[
            'pytest-cov', 'pytest-flake8', 'pytest-isort', 'pytest-runner'],
        extras_require={'test': (
            'pytest-runner', 'pytest-cov', 'pytest-flake8', 'pytest-isort')},
        packages=['tinycss', 'tinycss.tests'],
        **kwargs
    )


IS_PYPY = hasattr(sys, 'pypy_translation_info')
try:
    run_setup(not IS_PYPY)
except BuildFailed:
    BUILD_EXT_WARNING = ('WARNING: The extension could not be compiled, '
                         'speedups are not enabled.')
    print('*' * 75)
    print(BUILD_EXT_WARNING)
    print('Failure information, if any, is above.')
    print('Retrying the build without the Cython extension now.')
    print('*' * 75)

    run_setup(False)

    print('*' * 75)
    print(BUILD_EXT_WARNING)
    print('Plain-Python installation succeeded.')
    print('*' * 75)
