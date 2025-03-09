import mqtt, { MqttClient } from "mqtt";

const options = {
    host: import.meta.env.VITE_MQTT_HOST || "localhost",
    port: import.meta.env.VITE_MQTT_PORT || 1883,
    username: import.meta.env.VITE_MQTT_USERNAME || null,
    password: import.meta.env.VITE_MQTT_PASSWORD || null,
};

const client = (mqtt as any).connect(
    `${import.meta.env.VITE_MQTT_SCHEME}://${options.host}:${options.port}/ws`,
    options
);

client.on("connect", () => {
    console.log("Connected to MQTT broker");
});

client.on("error", (err) => {
    console.error("MQTT connection error:", err);
});

export default client as MqttClient;
