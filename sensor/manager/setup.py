#!/usr/bin/env python2
# -*- coding: utf-8 -*-

from setuptools import setup, find_packages

setup(
    name='honeysens-manager',
    version='1.0.0',
    description='HoneySens sensor management daemon',
    author='Pascal Brueckner',
    author_email='pascal.brueckner@sylence.cc',
    license='BSD',
    packages=find_packages(),
    install_requires=[
        'coloredlogs',
        'debinterface',
        'docker',
        'netifaces',
        'pycrypto',
        'pycurl==7.43.0.3',
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
