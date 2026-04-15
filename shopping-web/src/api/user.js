import request from "@/utils/request";

// 修改用户资料
export function updateUser(data) {
    return request({
        url: "/system/user",
        method: "put",
        data
    });
}