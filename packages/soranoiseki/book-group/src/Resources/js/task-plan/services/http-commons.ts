import axios, { AxiosInstance } from "axios";

const apiClient: AxiosInstance = axios.create({
    baseURL: import.meta.env.BASE_URL,
    headers: {
        "Content-type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

export default apiClient;
