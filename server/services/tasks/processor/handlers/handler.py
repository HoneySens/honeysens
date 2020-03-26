class HandlerInterface(object):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        """Has to be overwritten by actual handlers. Performs the handler task and returns a dict with results."""
        pass
