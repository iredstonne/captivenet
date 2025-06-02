const formatRemainingTime = (time: number) => {
    const seconds = Math.max(0, time)
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    const parts = []
    if (hours > 0) {
        parts.push(`${hours}h`);
    }
    if (minutes > 0 || hours > 0) {
        parts.push(`${minutes}m`);
    }
    parts.push(`${seconds % 60}s`);
    return parts.join(' ');
}

const remainingDeviceTimeElement = document.querySelector("#remaining_device_time") as HTMLSpanElement
if(remainingDeviceTimeElement) {
    if("value" in remainingDeviceTimeElement.dataset) {
        let remainingDeviceTime = parseInt(remainingDeviceTimeElement.dataset.value!)
        const updateRemainingDeviceTime = () => {
            if(remainingDeviceTime <= 0) {
                remainingDeviceTimeElement.textContent = "Expiré"
                remainingDeviceTimeElement.classList.add("text-red-500")
                return
            }
            remainingDeviceTime--
            remainingDeviceTimeElement.textContent = formatRemainingTime(remainingDeviceTime)
            setTimeout(updateRemainingDeviceTime, 1000)
        }
        updateRemainingDeviceTime()
    }
}
