// lib/axios.js
import axios from 'axios';


const axiosInstance = axios.create({
  baseURL: process.env.api || 'https://api.enaam.orb.local/api/v2/', // Replace with your API base URL
  timeout: 5000, // Request timeout in milliseconds
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': `application/vnd.YOUR_SUBTYPE.v1+json`

  },
});

export default axiosInstance;
