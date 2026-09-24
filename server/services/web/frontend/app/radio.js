import Radio from 'backbone.radio';

// Expose a single channel for both events and requests throughout the whole application.
export const radio = Radio.channel('honeysens');
