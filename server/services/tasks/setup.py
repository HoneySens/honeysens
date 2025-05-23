#!/usr/bin/env python3

from setuptools import setup, find_packages

setup(
    name='honeysens-task-processor',
    version='1.0.0',
    description='HoneySens task processor',
    author='Pascal Brueckner',
    author_mail='pascal.brueckner@sylence.cc',
    license='Apache 2.0',
    packages=find_packages(),
    install_requires=[
        'celery==5.*',
        'cryptography',
        'defusedxml',
        'pymysql',
        'redis'
    ]
)