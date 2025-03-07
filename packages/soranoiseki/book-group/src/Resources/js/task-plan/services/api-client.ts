import axios from "./http-commons";
import { AxiosResponse, AxiosError, AxiosRequestConfig, GenericAbortSignal } from "axios";
import { useToast } from "vue-toast-notification";

const toastr = useToast();

const handleAxiosError = (error: unknown) => {
    if ((error as AxiosError).name === "CanceledError") {
        return;
    }
    
    console.error(error);

    if ((error as AxiosError).response) {
        const response = (error as AxiosError)?.response?.data as
            | FailApiResponse
            | ServerErrorApiResponse;
        const statusCode = (error as AxiosError)?.response?.status;

        switch (statusCode) {
            case 404:
                toastr.error(
                    "请求的资源不存在"
                );
                break;
            case 422:
                for (let errors of Object.values(
                    (response as FailApiResponse).data
                )) {
                    for (let error of errors) {
                        toastr.error(error);
                    }
                }
                break;
            case 401:
            case 403:
            case 405:
            case 500:
                toastr.error(
                    (response as ServerErrorApiResponse).message ??
                        "未知错误"
                );
                break;
            default:
                toastr.error(
                    (error as AxiosError).message ??
                        "未知错误"
                );
        }
    } else {
        toastr.error("未知错误");
    }
};

const filterParams = (params: Object): Object => {
    const filteredParams: { [key: string]: any } = {};
    for (const [key, value] of Object.entries(params)) {
        if (typeof value === "string" && value.trim() === "") {
            continue;
        }
        if (Array.isArray(value) && value.length === 0) {
            continue;
        }
        filteredParams[key] = value;
    }
    return filteredParams;
};

const ApiClient = {
    async get<T>(endpoint: string, params: Object, signal: GenericAbortSignal | undefined = undefined): Promise<T | undefined> {
        const filteredParams = filterParams(params);
        return new Promise<T | undefined>((resolve, reject) => {
            axios
                .get<ApiResponse<T>>(endpoint, { params: filteredParams, signal: signal })
                .then((response) => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch((error) => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async post<T>(
        endpoint: string,
        data: Object,
        config: AxiosRequestConfig = {}
    ): Promise<T | undefined> {
        return new Promise<T | undefined>((resolve, reject) => {
            axios
                .post<ApiResponse<T>>(endpoint, data, config)
                .then((response) => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch((error) => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async put<T>(endpoint: string, data: any): Promise<T | undefined> {
        return new Promise<T | undefined>((resolve, reject) => {
            axios
                .put<ApiResponse<T>>(endpoint, data)
                .then((response) => {
                    if (response.data.success) {
                        resolve(response.data.data);
                    } else {
                        toastr.error(response.data.message);
                        resolve(undefined);
                    }
                })
                .catch((error) => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },

    async delete(endpoint: string): Promise<any | undefined> {
        return new Promise<any | undefined>((resolve, reject) => {
            axios
                .delete<any>(endpoint)
                .then((response) => {
                    if (response.data.status === "success") {
                        resolve(response.data);
                    } else {
                        toastr.error("未知错误");
                        resolve(undefined);
                    }
                })
                .catch((error) => {
                    handleAxiosError(error);
                    reject(error);
                });
        });
    },
};

export default ApiClient;
