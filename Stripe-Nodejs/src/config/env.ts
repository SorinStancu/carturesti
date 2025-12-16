
import dotenv from 'dotenv';
import path from 'path';

// Incarca variabilele de mediu din fisierul .env
dotenv.config({ path: path.resolve(__dirname, '../../.env') });

interface Config {
  stripe: {
    secretKey: string;
    webhookSecret: string;
  };
  server: {
    port: number;
    host: string;
  };
}

const getEnvVar = (key: string, defaultValue?: string): string => {
  const value = process.env[key] || defaultValue;
  if (!value) {
    throw new Error(`Environment variable ${key} is missing`);
  }
  return value;
};

export const config: Config = {
  stripe: {
    secretKey: getEnvVar('STRIPE_SECRET_KEY'),
    webhookSecret: getEnvVar('STRIPE_WEBHOOK_SECRET'),
  },
  server: {
    port: parseInt(process.env.PORT || '3000', 10),
    host: process.env.HOST || '0.0.0.0',
  },
};
