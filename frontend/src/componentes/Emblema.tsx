/** Emblema de Radioaficionados DX Ecuador. Decorativo por defecto: pasa `alt` si va solo, sin texto al lado. */
export function Emblema({ className = '', alt = '' }: { className?: string; alt?: string }) {
    return (
        <img
            src="/img/emblema-96.webp"
            srcSet="/img/emblema-96.webp 96w, /img/emblema-192.webp 192w, /img/emblema-512.webp 512w"
            sizes="(min-width: 1024px) 160px, 96px"
            alt={alt}
            width={96}
            height={96}
            decoding="async"
            className={`shrink-0 select-none drop-shadow-[0_4px_10px_rgb(0_0_0/0.35)] ${className}`}
        />
    );
}
