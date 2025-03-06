import axios, { AxiosInstance } from "axios";

const apiClient: AxiosInstance = axios.create({
    baseURL: '/api/planer',
    headers: {
        "Content-type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

export default apiClient;
