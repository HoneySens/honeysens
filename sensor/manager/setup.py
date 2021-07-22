#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from setuptools import setup, find_packages

setup(
    name='honeysens-manager',
    version='2.3.0',
    description='HoneySens sensor management daemon',
    author='Pascal Brueckner',
    author_email='pascal.brueckner@sylence.cc',
    license='Apache License 2.0',
    packages=find_packages(),
    install_requires=[
        'coloredlogs',
        'debinterface',
        'docker',
        'netifaces',
        'pycryptodomex',
        'pycurl',
        'pyyaml',
        'pyzmq'
    ],
    entry_points={
        'console_scripts': [
            'manager=manager.manager:main',
            'manager-cli=manager.cli:main'
        ]
    }
)
