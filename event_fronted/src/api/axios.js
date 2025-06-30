import axios from "axios";

const apiUrl = "http://localhost:8000/api/"

const api = axios.create({
  baseURL:  apiUrl,
  headers: {
    "Content-Type": "application/json",
  }

});

//Add a request interceptor (for authentication token if needed)
 api.interceptors.request.use((config) => {
    const token = localStorage.getItem("apiToken");

    if (token) {
        config.headers["Authorization"] = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
 });

//Add a response interceptor (for handling errors globally)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("API error:", error.response?.data || error.message);
    return Promise.reject(error);
  }
);
export default api;

