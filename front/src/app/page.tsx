import Link from "next/link";
import React from "react";

export default async function AppPage(props: PageProps<"/">) {
  return (
    <div className="w-full h-[60vh] flex justify-center items-center gap-32">
      <Link href="/csr">Client Side Rendering</Link>
      <Link href="/ssr">Server Side Rendering</Link>
      <Link href="/hydratation">Hydratation</Link>
    </div>
  );
}
