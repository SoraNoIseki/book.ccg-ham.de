import axios from "./http-commons";
import { AxiosResponse, AxiosError, AxiosRequestConfig } from "axios";


const handleAxiosError = (error: unknown) => {
    console.error(error);

    if ((error as AxiosError).response) {
        const response = (error as AxiosError)?.response?.data as FailApiResponse | ServerErrorApiResponse;
        const statusCode = (error as AxiosError)?.response?.status;

        switch (statusCode) {
            case 404:
                toastr.error("Die angeforderte Ressource wurde nicht gefunden.");
                break;
            case 422:
                for (let errors of Object.values((response as FailApiResponse).data)) {
                    for (let error of errors) {
                        toastr.error(error);
                    }
                }
                break;
            case 401:
            case 403:
            case 405:
            case 500:
                toastr.error((response as ServerErrorApiResponse).message ?? "Es ist ein Fehler aufgetreten.");
                break;
            default:
                toastr.error((error as AxiosError).message ?? "Es ist ein Fehler aufgetreten.");
        }
    } else {
        toastr.error("Es ist ein Fehler aufgetreten.");
    }
}

const filterParams = (params: Object): Object => {
    const filteredParams: { [key: string]: any } = {};
    for (const [key, value] of Object.entries(params)) {
        if (typeof value === 'string' && value.trim() === '') {
            continue;
        }
        if (Array.isArray(value) && value.length === 0) {
            continue;
        }
        filteredParams[key] = value;
    }
    return filteredParams;
}


const ApiClient = {

    async get<T>(endpoint: string, params: Object): Promise<T | undefined> {
        const filteredParams = filterParams(params);
       
        return new Promise<T | undefined>((resolve, reject) => {
            axios.get<ApiResponse<T>>(endpoint, { params: filteredParams })
                .then(response => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch(error => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async post<T>(endpoint: string, data: Object, config: AxiosRequestConfig = {}): Promise<T | undefined> {
        return new Promise<T | undefined>((resolve, reject) => {
            axios.post<ApiResponse<T>>(endpoint, data, config)
                .then(response => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch(error => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async put<T>(endpoint: string, data: any): Promise<T | undefined> {
        return new Promise<T | undefined>((resolve, reject) => {
            axios.put<ApiResponse<T>>(endpoint, data)
                .then(response => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch(error => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async delete(endpoint: string): Promise<any | undefined> {
        return new Promise<any | undefined>((resolve, reject) => {
            axios.delete<any>(endpoint)
                .then(response => {
                    if (response.data.status === 'success') {
                        resolve(response.data);
                    } else {
                        toastr.error('Es ist ein Fehler aufgetreten.');
                        resolve(undefined);
                    }
                })
                .catch(error => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

}

export default ApiClient;
