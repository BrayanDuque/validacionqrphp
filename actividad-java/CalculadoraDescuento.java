import java.util.Scanner;

public class CalculadoraDescuento {

    public static void main(String[] args) {

        Scanner scanner = new Scanner(System.in);

        System.out.print("Ingrese el nombre del producto: ");
        String producto = scanner.nextLine();

        System.out.print("Ingrese el precio del producto: ");
        double precio = scanner.nextDouble();

        double descuento = 0;

        if (precio > 100000) {
            descuento = precio * 0.10;
        }

        double valorFinal = precio - descuento;

        System.out.println("\n--- INFORMACIÓN DE COMPRA ---");
        System.out.println("Producto: " + producto);
        System.out.println("Valor original: $" + precio);
        System.out.println("Descuento: $" + descuento);
        System.out.println("Valor final: $" + valorFinal);

        scanner.close();
    }
}
