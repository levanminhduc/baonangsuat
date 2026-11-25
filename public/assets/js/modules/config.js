const meta = document.querySelector('meta[name="bns-base"]');

export const BASE_PATH = (meta ? meta.content : '/baonangsuat').replace(/\/$/, '');
export const API_BASE = BASE_PATH + '/api';
