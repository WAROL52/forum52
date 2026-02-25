import Link from "next/link";
import React from "react";

export default async function AppLayout(props: LayoutProps<"/">) {
  const params = await props.params;

  return (
    <div>
      <div className="container mx-auto bg-muted/50 border-t border-b h-[50vh] my-8">
        {props.children}
      </div>
      <footer>
        <div className="w-full flex justify-center items-center gap-32">
          <Link href="/">Home</Link>
          <Link href="/csr">Client Side Rendering</Link>
          <Link href="/ssr">Server Side Rendering</Link>
          <Link href="/hydratation">Hydratation</Link>
        </div>
      </footer>
    </div>
  );
}
