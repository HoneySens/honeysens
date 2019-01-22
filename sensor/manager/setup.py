#!/usr/bin/env python2
# -*- coding: utf-8 -*-

from setuptools import setup, find_packages

setup(
    name='honeysens-manager',
    version='18.12.01',
    description='HoneySens sensor management daemon',
    author='Pascal Brueckner',
    author_email='pascal.brueckner@sylence.cc',
    license='BSD',
    packages=find_packages(),
    install_requires=[
        'colorama',
        'docker',
        'netifaces',
        'pycrypto',
        'pycurl',
        'pyzmq',
        'debinterface'
    ],
    entry_points={
        'console_scripts': [
            'manager=manager.manager:main',
            'manager-cli=manager.cli:main'
        ]
    }
)
