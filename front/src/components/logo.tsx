"use client";

import Image from "next/image";

export type LogoProps = {};

export function Logo({}: LogoProps) {
  return (
    <span>
      <Image src="/logo.svg" alt="Logo" width={200} height={100} />
    </span>
  );
}
