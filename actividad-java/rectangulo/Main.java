public class Main {

    public static void main(String[] args) {

        Rectangulo rectangulo = new Rectangulo();

        rectangulo.base = 10;
        rectangulo.altura = 5;

        double area = rectangulo.calcularArea();
        double perimetro = rectangulo.calcularPerimetro();

        System.out.println("Base: " + rectangulo.base);
        System.out.println("Altura: " + rectangulo.altura);
        System.out.println("Área: " + area);
        System.out.println("Perímetro: " + perimetro);
    }
}
