import axios from "axios";

const api = axios.create({
  baseURL: "/api",
  headers: {
    "Content-Type": "application/json",
    "Accept": "application/json"
  }
});

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("token");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // let browser set correct multipart boundary for file uploads
  if (config.data instanceof FormData) {
    delete config.headers["Content-Type"]
  }

  return config;
});

export default api;