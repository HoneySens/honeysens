from __future__ import absolute_import

hooks = {}  # {hook => [cb1(), cb2(), ...], ...}


def register_hook(hook, callback):
    if hook not in hooks:
        hooks[hook] = []
    hooks[hook].append(callback)


def execute_hook(hook, args=()):
    if hook in hooks:
        for cb in hooks[hook]:
            cb(*args)