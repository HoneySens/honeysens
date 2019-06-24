#!/usr/bin/env python2
# -*- coding: utf-8 -*-

from setuptools import setup, find_packages

setup(
    name='honeysens-task-processor',
    version='1.0.0',
    description='HoneySens task processor',
    author='Pascal Brueckner',
    author_email='pascal.brueckner@sylence.cc',
    license='Apache 2.0',
    packages=find_packages(),
    install_requires=[
        'beanstalkc',
        'coloredlogs',
        'defusedxml',
        'pymysql'
    ],
    entry_points={
        'console_scripts': [
            'task-processor=tasks.tasks:main'
        ]
    }
)