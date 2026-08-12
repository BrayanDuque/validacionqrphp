import java.util.Scanner;

public class ConversorTemperatura {

    public static void main(String[] args) {

        Scanner scanner = new Scanner(System.in);

        System.out.print("Ingrese la temperatura en Celsius: ");
        double celsius = scanner.nextDouble();

        double fahrenheit = (celsius * 9 / 5) + 32;

        System.out.println("Temperatura en Celsius: " + celsius);
        System.out.println("Temperatura en Fahrenheit: " + fahrenheit);

        scanner.close();
    }
}
