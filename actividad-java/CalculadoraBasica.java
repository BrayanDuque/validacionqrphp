import java.util.Scanner;

public class CalculadoraBasica {

    public static void main(String[] args) {

        Scanner scanner = new Scanner(System.in);

        System.out.print("Ingrese el primer número: ");
        double numero1 = scanner.nextDouble();

        System.out.print("Ingrese el segundo número: ");
        double numero2 = scanner.nextDouble();

        System.out.println("\nSeleccione una operación:");
        System.out.println("1. Sumar");
        System.out.println("2. Restar");
        System.out.println("3. Multiplicar");
        System.out.println("4. Dividir");

        System.out.print("Ingrese una opción: ");
        int opcion = scanner.nextInt();

        double resultado;

        switch (opcion) {

            case 1:
                resultado = numero1 + numero2;
                System.out.println("Resultado: " + resultado);
                break;

            case 2:
                resultado = numero1 - numero2;
                System.out.println("Resultado: " + resultado);
                break;

            case 3:
                resultado = numero1 * numero2;
                System.out.println("Resultado: " + resultado);
                break;

            case 4:
                if (numero2 != 0) {
                    resultado = numero1 / numero2;
                    System.out.println("Resultado: " + resultado);
                } else {
                    System.out.println("No es posible dividir entre cero.");
                }
                break;

            default:
                System.out.println("Opción no válida.");
        }

        scanner.close();
    }
}
