import 'package:flutter/material.dart';

import '../models/book.dart';
import '../widgets/book_card.dart';

class CatalogScreen extends StatelessWidget {
  const CatalogScreen({super.key});

  static const List<Book> books = [
    Book(
      id: 1,
      title: 'Introduction au Développement Web',
      author: 'Dr. Messi',
      price: '5 000 FCFA',
      category: 'Développement',
    ),
    Book(
      id: 2,
      title: 'Maîtriser PHP et MySQL',
      author: 'Talla & Ivan',
      price: '3 500 FCFA',
      category: 'Programmation',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(20, 20, 20, 8),
            child: Text(
              'Catalogue',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),

          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 12,
            ),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Rechercher un livre...',
                prefixIcon: const Icon(Icons.search),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),

          Expanded(
            child: GridView.builder(
              padding: const EdgeInsets.all(20),
              itemCount: books.length,
              gridDelegate:
                  const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                childAspectRatio: 0.68,
              ),
              itemBuilder: (context, index) {
                return BookCard(
                  book: books[index],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}