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

// The server revoked our token (e.g. the account was suspended): drop the stale
// session and go to login, where the next attempt shows the suspension popup.
// Only for the logged-in session token; sign-up requests use their own token.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && sessionStorage.getItem("token")) {
      sessionStorage.removeItem("token");
      sessionStorage.removeItem("user");
      if (window.location.pathname !== "/login") {
        window.location.assign("/login");
      }
    }
    return Promise.reject(error);
  }
);

export default api;