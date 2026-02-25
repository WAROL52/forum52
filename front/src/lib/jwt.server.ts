import { type JWTPayload, jwtVerify, SignJWT } from "jose";

const secretKey = process.env.JWT_SECRET || "__DevPhantom_Default_Secret_Key__";
const key = new TextEncoder().encode(secretKey);

export async function encrypt(payload: JWTPayload) {
  return await new SignJWT(payload)
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime("7 days from now")
    .sign(key);
}

export async function decrypt<T>(input: string): Promise<T | null> {
  try {
    const { payload } = await jwtVerify(input, key, {
      algorithms: ["HS256"],
    });
    return payload as T;
  } catch (jwt_decrypt_error) {
    return null;
  }
}
